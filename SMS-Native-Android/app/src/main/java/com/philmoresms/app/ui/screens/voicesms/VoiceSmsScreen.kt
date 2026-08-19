package com.philmoresms.app.ui.screens.voicesms

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Text
import androidx.compose.runtime.*
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.ErrorUtils
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.ui.components.AppTopBar
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class VoiceSmsViewModel : ViewModel() {
    var callerId by mutableStateOf("")
    var recipients by mutableStateOf("")
    var message by mutableStateOf("")
    var sending by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    fun send() {
        error = null
        success = null
        sending = true
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.sendVoice(callerId, recipients, message).await()
                success = res.message ?: "Voice SMS sent successfully"
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to send voice SMS"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                sending = false
            }
        }
    }
}

@Composable
fun VoiceSmsScreen(navController: NavController) {
    val viewModel: VoiceSmsViewModel = viewModel()

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("Voice SMS", onBack = { navController.popBackStack() })

        Column(
            modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)
        ) {
            FintechInput("Caller ID", viewModel.callerId, { viewModel.callerId = it })
            FintechInput("Recipients (comma-separated)", viewModel.recipients, { viewModel.recipients = it })
            FintechInput("Message (spoken as voice)", viewModel.message, { viewModel.message = it })

            if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))
            if (viewModel.success != null) Text(viewModel.success ?: "", color = Success, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))

            FintechButton(if (viewModel.sending) "Sending..." else "Send Voice SMS", { viewModel.send() }, backgroundColor = VoiceColor)
            Spacer(Modifier.height(24.dp))
        }
    }
}
